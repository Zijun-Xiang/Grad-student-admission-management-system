from django.urls import path
from .views import (
    StudentList,
    StudentDetail,
    StudentsByProgram,
    StudentsByProfessor,
    UpdateAdvisor,
)

urlpatterns = [
    path("", StudentList.as_view()),
    path("<int:pk>/", StudentDetail.as_view()),
    path("program/<str:program_type>/", StudentsByProgram.as_view()),
    path("professor/<int:professor_id>/", StudentsByProfessor.as_view()),
    path("<int:pk>/advisor/", UpdateAdvisor.as_view()),
]
