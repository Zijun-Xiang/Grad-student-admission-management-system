from django.urls import path
from .views import (
    CourseList,
    CourseDetail,
    AddPrerequisite,
    AddPrerequisiteGroup,
    RemovePrerequisiteGroup,
)

urlpatterns = [
    path("", CourseList.as_view()),
    path("<int:pk>/", CourseDetail.as_view()),
    path("<int:pk>/prerequisites/", AddPrerequisite.as_view()),
    path("<int:pk>/prerequisite-groups/", AddPrerequisiteGroup.as_view()),
    path("<int:pk>/prerequisite-groups/<int:group_id>/", RemovePrerequisiteGroup.as_view()),
]
