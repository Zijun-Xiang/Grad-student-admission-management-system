from django.urls import path
from .views import (
    FacultyList,
    FacultyDetail,
    FacultyByTitle,
    FacultyByOffice,
    FacultyWithStudents,
)

urlpatterns = [
    path("", FacultyList.as_view()),
    path("<int:pk>/", FacultyDetail.as_view()),
    path("title/<str:title>/", FacultyByTitle.as_view()),
    path("office/<str:office>/", FacultyByOffice.as_view()),
    path("<int:pk>/students/", FacultyWithStudents.as_view()),
]
