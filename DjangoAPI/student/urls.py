from django.urls import path
from . import views

urlpatterns = [
    path('profile/', views.StudentProfileView.as_view(), name='student-profile'),
    path('program/', views.ProgramOfStudyListCreateView.as_view(), name='student-program'),
    path('program/<int:pk>/', views.ProgramOfStudyUpdateView.as_view(), name='program-update'),
    path('milestones/', views.MilestoneListCreateView.as_view(), name='milestones'),
    path('committee/', views.CommitteeListView.as_view(), name='committee'),
    path('documents/', views.StudentDocumentListCreateView.as_view(), name='documents'),
    path('notifications/', views.NotificationListView.as_view(), name='notifications'),
    path("choose-instructor/faculty/", views.select_faculty_view, name="select-faculty"),
    path("choose-instructor/create/", views.create_choose_instructor_view, name="create-choose-instructor"),
    path("choose-instructor/<int:pk>/upload/", views.upload_file_view, name="choose-instructor-upload"),
    path("choose-instructor/<int:pk>/submit/", views.submit_for_review_view, name="choose-instructor-submit"),
    path("choose-instructor/milestones/", views.milestone_tracker_view, name="choose-instructor-milestones"),
]
