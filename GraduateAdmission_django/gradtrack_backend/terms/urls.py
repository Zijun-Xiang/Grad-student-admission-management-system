from django.urls import path
from .views import TermListView, AddCourseToTermView, RemoveCourseFromTermView

urlpatterns = [
    path("<int:student_id>/", TermListView.as_view()),
    path("<int:student_id>/terms/<int:term_id>/courses/", AddCourseToTermView.as_view()),
    path("<int:student_id>/terms/<int:term_id>/courses/<int:course_id>/", RemoveCourseFromTermView.as_view()),
]
