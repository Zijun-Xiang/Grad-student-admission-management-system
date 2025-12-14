from django.urls import path
from .views import (
    MilestoneListView,
    DeadlineListView,
    ScrapedDeadlineListView,
    EvaluationListView,
    NotificationListView,
)

urlpatterns = [
    path("milestones/", MilestoneListView.as_view()),
    path("deadlines/", DeadlineListView.as_view()),
    path("deadlines/scraped/", ScrapedDeadlineListView.as_view()),
    path("evaluations/", EvaluationListView.as_view()),
    path("notifications/", NotificationListView.as_view()),
]
