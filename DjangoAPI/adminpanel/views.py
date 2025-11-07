from rest_framework import viewsets, permissions
from .models import Workflow, Report, ComplianceItem, SystemSetting, Person
from .serializers import (
    WorkflowSerializer, ReportSerializer, ComplianceSerializer,
    SystemSettingSerializer, UserSerializer, PersonSerializer
)
from django.contrib.auth.models import User
from django.shortcuts import render


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

