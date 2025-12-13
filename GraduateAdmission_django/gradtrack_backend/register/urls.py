from django.urls import path
from .views import MajorCompletionView, RegisterView


urlpatterns = [
    path("<int:student_id>/", MajorCompletionView.as_view()),
]
