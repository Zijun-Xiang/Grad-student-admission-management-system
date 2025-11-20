"""DjangoAPI URL Configuration

The `urlpatterns` list routes URLs to views. For more information please see:
    https://docs.djangoproject.com/en/3.2/topics/http/urls/
Examples:
Function views
    1. Add an import:  from my_app import views
    2. Add a URL to urlpatterns:  path('', views.home, name='home')
Class-based views
    1. Add an import:  from other_app.views import Home
    2. Add a URL to urlpatterns:  path('', Home.as_view(), name='home')
Including another URLconf
    1. Import the include() function: from django.urls import include, path
    2. Add a URL to urlpatterns:  path('blog/', include('blog.urls'))
"""
from django.urls import path
from . import views

urlpatterns = [
    path('profile/', views.FacultyProfileView.as_view(), name='faculty-profile'),
    path('advisees/', views.FacultyAdviseeListView.as_view(), name='faculty-advisees'),
    path('progress/', views.FacultyAdviseeProgressView.as_view(), name='advisee-progress'),
    path('approvals/', views.ApprovalRequestListCreateView.as_view(), name='approval-list'),
    path('approvals/<int:pk>/', views.ApprovalRequestUpdateView.as_view(), name='approval-update'),
    path('evaluations/', views.EvaluationReportListCreateView.as_view(), name='evaluation'),
    path('notifications/', views.FacultyNotificationListView.as_view(), name='faculty-notifications'),
    path('choose-instructor/requests/', views.approval_requests_list_view),
    path('choose-instructor/requests/<int:pk>/', views.approval_request_detail_view),
    path('choose-instructor/requests/<int:pk>/approve/', views.approve_request_view),
    path('choose-instructor/requests/<int:pk>/reject/', views.reject_request_view),
    path('choose-instructor/requests/new-count/', views.new_submission_count_view),
]
