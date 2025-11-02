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
]
