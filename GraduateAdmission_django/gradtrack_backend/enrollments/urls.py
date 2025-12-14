from django.urls import path
from .views import EnrollmentList, EnrollmentDetail

urlpatterns = [
    path("", EnrollmentList.as_view()),
    path("<int:pk>/", EnrollmentDetail.as_view()),
]
