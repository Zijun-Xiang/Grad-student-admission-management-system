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
router.register('persons', PersonViewSet)

urlpatterns = [
    path('', include(router.urls)),
]
