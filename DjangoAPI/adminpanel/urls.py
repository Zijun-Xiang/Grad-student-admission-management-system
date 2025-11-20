from django.urls import path, include
from rest_framework.routers import DefaultRouter
from .views import (
    UserViewSet, WorkflowViewSet, ReportViewSet,
    ComplianceViewSet, SystemSettingViewSet, PersonViewSet,
    choose_instructor_list_view, choose_instructor_detail_view,
    choose_instructor_approve_view, choose_instructor_reject_view,
)

router = DefaultRouter()
router.register('users', UserViewSet)
router.register('workflows', WorkflowViewSet)
router.register('reports', ReportViewSet)
router.register('compliance', ComplianceViewSet)
router.register('settings', SystemSettingViewSet)
router.register('persons', PersonViewSet)  # 👈 新增
from .views import RegisterView, LoginView




urlpatterns = [
    path('', include(router.urls)),
    path("register/", RegisterView.as_view(), name="register"),
    path("login/", LoginView.as_view(), name="login"),  # ⭐ 新增
    path("choose-instructor/", choose_instructor_list_view),
    path("choose-instructor/<int:pk>/", choose_instructor_detail_view),
    path("choose-instructor/<int:pk>/approve/", choose_instructor_approve_view),
    path("choose-instructor/<int:pk>/reject/", choose_instructor_reject_view),
]
