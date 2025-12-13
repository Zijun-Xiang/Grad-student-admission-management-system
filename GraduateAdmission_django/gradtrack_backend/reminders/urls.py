from django.urls import path
from .views import (
    ReminderListView,
    ReminderDetailView,
    SendReminderToStudentView,
)

urlpatterns = [
    path("", ReminderListView.as_view()),
    path("<int:pk>/", ReminderDetailView.as_view()),
    path("students/<int:student_id>/", SendReminderToStudentView.as_view()),
]
