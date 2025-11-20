from django.urls import path, include
from rest_framework.routers import DefaultRouter
from .views import (
    UserViewSet, WorkflowViewSet, ReportViewSet,
    ComplianceViewSet, SystemSettingViewSet, PersonViewSet,

    # faculty
    faculty_list_view,

    # student actions
    upload_file_view,
    submit_pos_view,

    # admin choose instructor actions
    choose_instructor_list_view,
    choose_instructor_detail_view,
    choose_instructor_approve_view,
    choose_instructor_reject_view,

    RegisterView, LoginView,
)

# DRF Router
router = DefaultRouter()
router.register('users', UserViewSet)
router.register('workflows', WorkflowViewSet)
router.register('reports', ReportViewSet)
router.register('compliance', ComplianceViewSet)
router.register('settings', SystemSettingViewSet)
router.register('persons', PersonViewSet)

urlpatterns = [
    path('', include(router.urls)),

    # ===== Auth =====
    path("register/", RegisterView.as_view(), name="register"),
    path("login/", LoginView.as_view(), name="login"),

    # ===== Student available APIs =====
    path("faculty-list/", faculty_list_view, name="faculty-list"),

    # Student: Upload POS file
    path("choose-instructor/<int:pk>/upload-file/", upload_file_view),

    # Student: Submit POS
    path("choose-instructor/<int:pk>/submit/", submit_pos_view),

    # ===== Admin APIs =====
    path("choose-instructor/", choose_instructor_list_view),
    path("choose-instructor/<int:pk>/", choose_instructor_detail_view),
    path("choose-instructor/<int:pk>/approve/", choose_instructor_approve_view),
    path("choose-instructor/<int:pk>/reject/", choose_instructor_reject_view),
]



