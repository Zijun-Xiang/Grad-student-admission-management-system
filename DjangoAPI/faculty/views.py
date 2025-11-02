from rest_framework import generics, permissions
from .models import *
from .serializers import *
from student.models import StudentProfile
from django.shortcuts import render
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
