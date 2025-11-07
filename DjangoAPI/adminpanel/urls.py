from django.urls import path, include
from rest_framework.routers import DefaultRouter
from .views import (
    UserViewSet, WorkflowViewSet, ReportViewSet,
    ComplianceViewSet, SystemSettingViewSet, PersonViewSet
)

router = DefaultRouter()
router.register('users', UserViewSet)
router.register('workflows', WorkflowViewSet)
router.register('reports', ReportViewSet)
router.register('compliance', ComplianceViewSet)
router.register('settings', SystemSettingViewSet)
router.register('persons', PersonViewSet)  # 👈 新增
from .views import RegisterView



urlpatterns = [
    path('', include(router.urls)),
    path("register/", RegisterView.as_view(), name="register"),
]
