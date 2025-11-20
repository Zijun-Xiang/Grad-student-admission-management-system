from django.shortcuts import get_object_or_404, render
from django.utils import timezone
from rest_framework import generics, permissions, status
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response

from .models import *
from .serializers import *
from student.models import ChooseInstructor, StudentProfile
# 导师个人信息
class FacultyProfileView(generics.RetrieveUpdateAPIView):
    queryset = FacultyProfile.objects.all()
    serializer_class = FacultyProfileSerializer
    permission_classes = [permissions.IsAuthenticated]


# 导师指导学生
class FacultyAdviseeListView(generics.ListAPIView):
    serializer_class = FacultyAdviseeSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return FacultyAdvisee.objects.filter(faculty__user=self.request.user)


# 学生进度汇总
class FacultyAdviseeProgressView(generics.ListAPIView):
    serializer_class = FacultyAdviseeSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return FacultyAdvisee.objects.filter(faculty__user=self.request.user)


# 审批请求（审批页面）
class ApprovalRequestListCreateView(generics.ListCreateAPIView):
    serializer_class = ApprovalRequestSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return ApprovalRequest.objects.filter(faculty__user=self.request.user)


class ApprovalRequestUpdateView(generics.RetrieveUpdateAPIView):
    queryset = ApprovalRequest.objects.all()
    serializer_class = ApprovalRequestSerializer
    permission_classes = [permissions.IsAuthenticated]


# 评估报告
class EvaluationReportListCreateView(generics.ListCreateAPIView):
    serializer_class = EvaluationReportSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return EvaluationReport.objects.filter(faculty__user=self.request.user)


# 通知
class FacultyNotificationListView(generics.ListAPIView):
    serializer_class = FacultyNotificationSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return FacultyNotification.objects.filter(faculty__user=self.request.user)


# Create your views here.


@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def approval_requests_list_view(request):
    """List submissions assigned to the logged-in faculty, newest first."""
    faculty_profile = get_object_or_404(FacultyProfile, user=request.user)
    records = ChooseInstructor.objects.filter(facultyId=faculty_profile.id).order_by("-submittedAt")

    data = [
        {
            "id": ci.id,
            "studentName": ci.studentName,
            "state": ci.state,
            "studentComment": ci.studentComment,
            "facultyComment": ci.facultyComment,
            "submittedAt": ci.submittedAt,
            "reviewedAt": ci.reviewedAt,
            "file": ci.file.url if ci.file else None,
        }
        for ci in records
    ]
    return Response(data, status=status.HTTP_200_OK)


@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def approval_request_detail_view(request, pk):
    """Return one submission detail (must belong to logged-in faculty)."""
    faculty_profile = get_object_or_404(FacultyProfile, user=request.user)
    choose = get_object_or_404(ChooseInstructor, id=pk, facultyId=faculty_profile.id)

    data = {
        "id": choose.id,
        "studentName": choose.studentName,
        "studentComment": choose.studentComment,
        "facultyComment": choose.facultyComment,
        "state": choose.state,
        "submittedAt": choose.submittedAt,
        "reviewedAt": choose.reviewedAt,
        "file": choose.file.url if choose.file else None,
    }
    return Response(data, status=status.HTTP_200_OK)


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def approve_request_view(request, pk):
    """Approve a submission assigned to the logged-in faculty."""
    faculty_profile = get_object_or_404(FacultyProfile, user=request.user)
    choose = get_object_or_404(ChooseInstructor, id=pk, facultyId=faculty_profile.id)

    choose.state = ChooseInstructor.STATE_APPROVED
    choose.facultyComment = request.data.get("facultyComment", "")
    choose.reviewedAt = timezone.now()
    choose.save(update_fields=["state", "facultyComment", "reviewedAt"])
    return Response({"detail": "Approved."}, status=status.HTTP_200_OK)


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def reject_request_view(request, pk):
    """Reject a submission assigned to the logged-in faculty."""
    faculty_profile = get_object_or_404(FacultyProfile, user=request.user)
    choose = get_object_or_404(ChooseInstructor, id=pk, facultyId=faculty_profile.id)

    choose.state = ChooseInstructor.STATE_REJECTED
    choose.facultyComment = request.data.get("facultyComment", "")
    choose.reviewedAt = timezone.now()
    choose.save(update_fields=["state", "facultyComment", "reviewedAt"])
    return Response({"detail": "Rejected."}, status=status.HTTP_200_OK)


@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def new_submission_count_view(request):
    """Return count of pending submissions for the logged-in faculty."""
    faculty_profile = get_object_or_404(FacultyProfile, user=request.user)
    count = ChooseInstructor.objects.filter(
        facultyId=faculty_profile.id, state=ChooseInstructor.STATE_PENDING
    ).count()
    return Response({"newSubmissions": count}, status=status.HTTP_200_OK)
