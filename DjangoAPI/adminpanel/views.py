from rest_framework import viewsets, permissions, status
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response
from rest_framework.views import APIView

from django.contrib.auth.models import User
from django.shortcuts import get_object_or_404
from django.utils import timezone

from .models import Workflow, Report, ComplianceItem, SystemSetting, Person
from .serializers import (
    WorkflowSerializer, ReportSerializer, ComplianceSerializer,
    SystemSettingSerializer, UserSerializer, PersonSerializer,
    RegisterSerializer, LoginSerializer, ChooseInstructorAdminSerializer
)
from student.models import ChooseInstructor
from faculty.models import FacultyProfile


# ============================
# Faculty List API
# ============================
@api_view(['GET'])
def faculty_list_view(request):
    faculties = (
        FacultyProfile.objects.select_related("user")
        .all()
        .values("id", "faculty_id", "department", "user__first_name", "user__last_name", "user__username")
    )

    payload = []
    for f in faculties:
        full_name = (f.get("user__first_name") or "") + " " + (f.get("user__last_name") or "")
        full_name = full_name.strip() or f.get("user__username") or f["faculty_id"]
        payload.append(
            {
                "user_id": f["id"],
                "name": full_name,
                "department": f["department"],
                "facultyId": f["faculty_id"],
            }
        )

    return Response({"success": True, "faculty": payload}, status=200)



# ============================
# Student Upload POS File
# ============================
@api_view(['POST'])
def upload_file_view(request, pk):
    """Upload a Program of Study file for an existing ChooseInstructor record."""

    student_id = request.data.get("student_id") or request.POST.get("student_id")
    if not student_id:
        return Response({"success": False, "message": "Missing student_id"}, status=400)

    choose = get_object_or_404(ChooseInstructor, id=pk, studentId=student_id)

    uploaded_file = request.FILES.get("file")
    if not uploaded_file:
        return Response({"success": False, "message": "No file received."}, status=400)

    choose.file = uploaded_file
    choose.save(update_fields=["file"])

    return Response({"success": True, "message": "File uploaded successfully."})



# ============================
# Student Submit POS to Advisor
# ============================
@api_view(['POST'])
def submit_pos_view(request, pk):
    """Finalize the submission of a Program of Study for faculty review."""

    student_id = request.data.get("student_id") or request.POST.get("student_id")
    if not student_id:
        return Response({"success": False, "message": "Missing student_id"}, status=400)

    choose = get_object_or_404(ChooseInstructor, id=pk, studentId=student_id)

    if not choose.file:
        return Response({"success": False, "message": "Please upload a file before submitting."}, status=400)

    choose.studentComment = request.data.get("studentComment", "")
    choose.submittedAt = timezone.now()
    choose.state = ChooseInstructor.STATE_PENDING
    choose.save(update_fields=["studentComment", "submittedAt", "state"])

    return Response({"success": True, "message": "Submitted for review."})



# ============================
# Zijun Xiang部分
# ============================
class RegisterView(APIView):
    def post(self, request):
        serializer = RegisterSerializer(data=request.data)

        if serializer.is_valid():
            user = serializer.validated_data["person"]
            new_password = serializer.validated_data["password"]
            user.password = new_password
            user.save()

            return Response({"message": "Registration successful."}, status=200)

        return Response({"error": serializer.errors}, status=400)



class LoginView(APIView):
    def post(self, request):
        user_id = request.data.get("user_id")
        password = request.data.get("password")

        # 固定管理员账号（无需数据库）
        if user_id == "admin" and password == "123":
            return Response({
                "message": "Login successful",
                "user_id": "admin",
                "name": "System Administrator",
                "department": "Admin",
                "identity": "Admin"
            }, status=200)

        # 普通账号使用 serializer
        serializer = LoginSerializer(data=request.data)
        if serializer.is_valid():
            person = serializer.validated_data["person"]
            return Response({
                "message": "Login successful",
                "user_id": person.user_id,
                "name": person.name,
                "department": person.department,
                "identity": person.identity,
            }, status=200)

        return Response({"error": serializer.errors}, status=400)



# ============================
# Admin permission
# ============================
class IsAdmin(permissions.BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.is_staff



# ============================
# Admin CRUD Views
# ============================
class UserViewSet(viewsets.ModelViewSet):
    queryset = User.objects.all()
    serializer_class = UserSerializer
    permission_classes = [IsAdmin]


class PersonViewSet(viewsets.ModelViewSet):
    queryset = Person.objects.all()
    serializer_class = PersonSerializer
    permission_classes = []


class WorkflowViewSet(viewsets.ModelViewSet):
    queryset = Workflow.objects.all()
    serializer_class = WorkflowSerializer
    permission_classes = [IsAdmin]


class ReportViewSet(viewsets.ModelViewSet):
    queryset = Report.objects.all()
    serializer_class = ReportSerializer
    permission_classes = [IsAdmin]


class ComplianceViewSet(viewsets.ModelViewSet):
    queryset = ComplianceItem.objects.all()
    serializer_class = ComplianceSerializer
    permission_classes = [IsAdmin]


class SystemSettingViewSet(viewsets.ModelViewSet):
    queryset = SystemSetting.objects.all()
    serializer_class = SystemSettingSerializer
    permission_classes = [IsAdmin]



# ============================
# Admin View: ChooseInstructor
# ============================
@api_view(["GET"])
def choose_instructor_list_view(request):
    records = ChooseInstructor.objects.all().order_by("-submittedAt")
    serializer = ChooseInstructorAdminSerializer(records, many=True)
    return Response(serializer.data, status=200)


@api_view(["GET", "PUT", "PATCH", "DELETE"])
def choose_instructor_detail_view(request, pk):
    choose = get_object_or_404(ChooseInstructor, id=pk)

    if request.method == "DELETE":
        choose.delete()
        return Response(status=204)

    if request.method in ["PUT", "PATCH"]:
        serializer = ChooseInstructorAdminSerializer(choose, data=request.data, partial=True)
        serializer.is_valid(raise_exception=True)
        serializer.save()
        return Response(serializer.data)

    serializer = ChooseInstructorAdminSerializer(choose)
    return Response(serializer.data)



@api_view(["POST"])
def choose_instructor_approve_view(request, pk):
    choose = get_object_or_404(ChooseInstructor, id=pk)
    choose.state = ChooseInstructor.STATE_APPROVED
    choose.facultyComment = request.data.get("facultyComment", "")
    choose.reviewedAt = timezone.now()
    choose.save()
    return Response({"success": True})


@api_view(["POST"])
def choose_instructor_reject_view(request, pk):
    choose = get_object_or_404(ChooseInstructor, id=pk)
    choose.state = ChooseInstructor.STATE_REJECTED
    choose.facultyComment = request.data.get("facultyComment", "")
    choose.reviewedAt = timezone.now()
    choose.save()
    return Response({"success": True})
