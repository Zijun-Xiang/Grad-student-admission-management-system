from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status
from django.shortcuts import get_object_or_404

from .models import Course, PrerequisiteGroup
from .serializers import (
    CourseSerializer,
    CourseCreateUpdateSerializer,
    PrerequisiteGroupSerializer,
)


# ------------------------------
# GET /courses
# POST /courses
# ------------------------------
class CourseList(APIView):
    def get(self, request):
        level = request.query_params.get("level")
        queryset = Course.objects.all().order_by("course_code")

        if level:
            queryset = queryset.filter(level=level)

        return Response(CourseSerializer(queryset, many=True).data)

    def post(self, request):
        serializer = CourseCreateUpdateSerializer(data=request.data)
        if serializer.is_valid():
            course = serializer.save()
            return Response(
                {"message": "Course created successfully", "course": CourseSerializer(course).data},
                status=201,
            )
        return Response(serializer.errors, status=400)


# ------------------------------
# GET /courses/{id}
# PUT/PATCH /courses/{id}
# DELETE /courses/{id}
# ------------------------------
class CourseDetail(APIView):
    def get(self, request, pk):
        course = get_object_or_404(Course, pk=pk)
        return Response(CourseSerializer(course).data)

    def put(self, request, pk):
        course = get_object_or_404(Course, pk=pk)
        serializer = CourseCreateUpdateSerializer(course, data=request.data)
        if serializer.is_valid():
            serializer.save()
            return Response(
                {"message": "Course updated successfully", "course": CourseSerializer(course).data}
            )
        return Response(serializer.errors, status=400)

    def patch(self, request, pk):
        course = get_object_or_404(Course, pk=pk)
        serializer = CourseCreateUpdateSerializer(course, data=request.data, partial=True)
        if serializer.is_valid():
            serializer.save()
            return Response(
                {"message": "Course updated successfully", "course": CourseSerializer(course).data}
            )
        return Response(serializer.errors, status=400)

    def delete(self, request, pk):
        course = get_object_or_404(Course, pk=pk)
        course.prerequisites.clear()
        course.prerequisite_groups.all().delete()
        course.delete()
        return Response({"message": "Course deleted"})


# ------------------------------
# POST /courses/{id}/prerequisites
# ------------------------------
class AddPrerequisite(APIView):
    def post(self, request, pk):
        course = get_object_or_404(Course, pk=pk)
        prerequisite_id = request.data.get("prerequisite_id")

        prereq = get_object_or_404(Course, pk=prerequisite_id)

        if course.prerequisites.filter(id=prerequisite_id).exists():
            return Response({"message": "Prerequisite already added"}, status=400)

        course.prerequisites.add(prereq)
        return Response({"message": "Prerequisite added successfully"})


# ------------------------------
# POST /courses/{id}/prerequisite-groups
# ------------------------------
class AddPrerequisiteGroup(APIView):
    def post(self, request, pk):
        course = get_object_or_404(Course, pk=pk)
        prereq_ids = request.data.get("prerequisite_ids", [])

        if not prereq_ids:
            return Response({"error": "prerequisite_ids is required"}, status=400)

        group = PrerequisiteGroup.objects.create(course=course)
        group.prerequisites.add(*prereq_ids)

        return Response({"message": "Prerequisite group added successfully"})


# ------------------------------
# DELETE /courses/{id}/prerequisite-groups/{group_id}
# ------------------------------
class RemovePrerequisiteGroup(APIView):
    def delete(self, request, pk, group_id):
        course = get_object_or_404(Course, pk=pk)
        group = get_object_or_404(PrerequisiteGroup, pk=group_id, course=course)

        group.prerequisites.clear()
        group.delete()

        return Response({"message": "Prerequisite group removed successfully"})

