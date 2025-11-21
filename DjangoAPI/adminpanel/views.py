from rest_framework import viewsets, permissions, status
from rest_framework.decorators import api_view, permission_classes
from rest_framework.views import APIView
from rest_framework.response import Response

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

# faculty_list_view
# ============================
# Faculty List API（使用 MySQL 查询 users 表）
# ============================

from rest_framework.decorators import api_view
from rest_framework.response import Response
import mysql.connector
from DjangoAPI.settings import DATABASES

@api_view(['POST'])
def upload_file_view(request, pk):
    """
    Student uploads Program of Study file
    pk = faculty.user_id  (前端传入的是导师ID)
    """

    try:
        # 确认学生身份（前端登录后必须传 student_id）
        student_id = request.data.get("student_id")
        if not student_id:
            return Response({"success": False, "message": "Missing student_id"}, status=400)

        # 获取/创建 ChooseInstructor 记录（1学生 + 1导师 只有1条）
        choose, created = ChooseInstructor.objects.get_or_create(
            student_id=student_id,
            faculty_id=pk,     # 这里 pk 是 faculty.user_id
        )

        uploaded_file = request.FILES.get("file")
        if not uploaded_file:
            return Response({"success": False, "message": "No file received."}, status=400)

        choose.document = uploaded_file
        choose.save(update_fields=["document"])

        return Response({"success": True, "message": "File uploaded successfully."})

    except Exception as e:

        return Response({"success": False, "message": str(e)}, status=500)


@api_view(['POST'])
def submit_pos_view(request, pk):
    """
    Student submits POS for review
    pk = faculty.user_id
    """

    try:
        student_id = request.data.get("student_id")
        if not student_id:
            return Response({"success": False, "message": "Missing student_id"}, status=400)

        choose, created = ChooseInstructor.objects.get_or_create(
            student_id=student_id,
            faculty_id=pk,
        )

        choose.studentComment = request.data.get("studentComment", "")
        choose.submittedAt = timezone.now()
        choose.state = ChooseInstructor.STATE_PENDING
        choose.save(update_fields=["studentComment", "submittedAt", "state"])

        return Response({"success": True, "message": "Submitted for review."})

    except Exception as e:
        return Response({"success": False, "message": str(e)}, status=500)


@api_view(['GET'])
def faculty_list_view(request):
    try:
        # 连接 MySQL
        conn = mysql.connector.connect(
            host=DATABASES['default']['HOST'],
            user=DATABASES['default']['USER'],
            password=DATABASES['default']['PASSWORD'],
            database=DATABASES['default']['NAME']
        )

        cursor = conn.cursor(dictionary=True)

        # 查询所有 Faculty
        query = """
            SELECT user_id, name, department
            FROM users
            WHERE identity = 'Faculty';
        """
        cursor.execute(query)
        faculty_rows = cursor.fetchall()

        cursor.close()
        conn.close()

        return Response({
            "success": True,
            "faculty": faculty_rows
        }, status=200)

    except Exception as e:
        return Response({
            "success": False,
            "error": str(e)
        }, status=500)





############Zijun Xiang
class RegisterView(APIView):
    def post(self, request):
        serializer = RegisterSerializer(data=request.data)

        if serializer.is_valid():
            user = serializer.validated_data["person"]
            new_password = serializer.validated_data["password"]

            # ✅ 设置新密码
            user.password = new_password
            user.save()

            return Response({"message": "Registration successful."}, status=status.HTTP_200_OK)

        return Response({"error": serializer.errors}, status=status.HTTP_400_BAD_REQUEST)
class LoginView(APIView):
    def post(self, request):
        user_id = request.data.get("user_id")
        password = request.data.get("password")

        # 1. 处理固定管理员账号 admin / 123（不查数据库）
        if user_id == "admin" and password == "123":
            return Response({
                "message": "Login successful",
                "user_id": "admin",
                "name": "System Administrator",
                "department": "Admin",
                "identity": "Admin",   # 前端根据这个跳转
            }, status=status.HTTP_200_OK)

        # 2. 普通账号 → 使用 LoginSerializer 校验（查数据库）
        serializer = LoginSerializer(data=request.data)

        if serializer.is_valid():
            person = serializer.validated_data["person"]

            return Response({
                "message": "Login successful",
                "user_id": person.user_id,
                "name": person.name,
                "department": person.department,
                "identity": person.identity,
            }, status=status.HTTP_200_OK)

        return Response({"error": serializer.errors}, status=status.HTTP_400_BAD_REQUEST)

############Zijun Xiang

# 自定义权限（仅管理员）
class IsAdmin(permissions.BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.is_staff


# 系统用户管理
class UserViewSet(viewsets.ModelViewSet):
    queryset = User.objects.all()
    serializer_class = UserSerializer
    permission_classes = [IsAdmin]


# ========== 新增：Person 表管理 ==========
class PersonViewSet(viewsets.ModelViewSet):
    queryset = Person.objects.all().order_by('-created_at')
    serializer_class = PersonSerializer
    # 可根据需要设置权限，比如仅管理员可操作：
    # permission_classes = [IsAdmin]
    # 若前端测试方便，可先放开：
    permission_classes = []


# 工作流管理
class WorkflowViewSet(viewsets.ModelViewSet):
    queryset = Workflow.objects.all().order_by('-created_at')
    serializer_class = WorkflowSerializer
    permission_classes = [IsAdmin]


# 报表管理
class ReportViewSet(viewsets.ModelViewSet):
    queryset = Report.objects.all().order_by('-created_at')
    serializer_class = ReportSerializer
    permission_classes = [IsAdmin]


# 合规检查
class ComplianceViewSet(viewsets.ModelViewSet):
    queryset = ComplianceItem.objects.all()
    serializer_class = ComplianceSerializer
    permission_classes = [IsAdmin]


# 系统设置
class SystemSettingViewSet(viewsets.ModelViewSet):
    queryset = SystemSetting.objects.all()
    serializer_class = SystemSettingSerializer
    permission_classes = [IsAdmin]


@api_view(["GET"])
@permission_classes([IsAdmin])
def choose_instructor_list_view(request):
    """Admin: list all ChooseInstructor records."""
    records = ChooseInstructor.objects.all().order_by("-submittedAt")
    serializer = ChooseInstructorAdminSerializer(records, many=True)
    return Response(serializer.data, status=status.HTTP_200_OK)


@api_view(["GET", "PUT", "PATCH", "DELETE"])
@permission_classes([IsAdmin])
def choose_instructor_detail_view(request, pk):
    """Admin: retrieve, update, or delete a specific ChooseInstructor record."""
    choose = get_object_or_404(ChooseInstructor, id=pk)

    if request.method == "DELETE":
        choose.delete()
        return Response(status=status.HTTP_204_NO_CONTENT)

    if request.method in ["PUT", "PATCH"]:
        serializer = ChooseInstructorAdminSerializer(
            choose, data=request.data, partial=True
        )
        serializer.is_valid(raise_exception=True)
        serializer.save()
        return Response(serializer.data, status=status.HTTP_200_OK)

    serializer = ChooseInstructorAdminSerializer(choose)
    return Response(serializer.data, status=status.HTTP_200_OK)


@api_view(["POST"])
@permission_classes([IsAdmin])
def choose_instructor_approve_view(request, pk):
    """Admin: approve any ChooseInstructor record."""
    choose = get_object_or_404(ChooseInstructor, id=pk)
    choose.state = ChooseInstructor.STATE_APPROVED
    choose.facultyComment = request.data.get("facultyComment", "")
    choose.reviewedAt = timezone.now()
    choose.save(update_fields=["state", "facultyComment", "reviewedAt"])
    serializer = ChooseInstructorAdminSerializer(choose)
    return Response(serializer.data, status=status.HTTP_200_OK)


@api_view(["POST"])
@permission_classes([IsAdmin])
def choose_instructor_reject_view(request, pk):
    """Admin: reject any ChooseInstructor record."""
    choose = get_object_or_404(ChooseInstructor, id=pk)
    choose.state = ChooseInstructor.STATE_REJECTED
    choose.facultyComment = request.data.get("facultyComment", "")
    choose.reviewedAt = timezone.now()
    choose.save(update_fields=["state", "facultyComment", "reviewedAt"])
    serializer = ChooseInstructorAdminSerializer(choose)
    return Response(serializer.data, status=status.HTTP_200_OK)


# ========== Faculty 列表 API（Student Portal 用） ==========

