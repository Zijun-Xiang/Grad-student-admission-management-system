from django.urls import path
from .views import AdvisorDetailView, AdvisorMessageView

urlpatterns = [
    path("<int:student_id>/", AdvisorDetailView.as_view()),
    path("message/", AdvisorMessageView.as_view()),
]
