from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status
from django.shortcuts import get_object_or_404

from .models import Faculty
from .serializers import FacultySerializer, FacultyCreateUpdateSerializer
from users.models import User

# ------------------------------
# GET /faculty
# ------------------------------
class FacultyList(APIView):
    def get(self, request):
        queryset = Faculty.objects.select_related("faculty_id").all()

        title = request.query_params.get("title")
        office = request.query_params.get("office")

        if title:
            queryset = queryset.filter(title__icontains=title)
        if office:
            queryset = queryset.filter(office__icontains=office)

        return Response({"faculty": FacultySerializer(queryset, many=True).data})

    def post(self, request):
        faculty_user = get_object_or_404(User, pk=request.data.get("faculty_id"))

        if faculty_user.role != "faculty":
            return Response(
                {"message": "User must have faculty role"},
                status=status.HTTP_422_UNPROCESSABLE_ENTITY
            )

        serializer = FacultyCreateUpdateSerializer(data=request.data)
        if serializer.is_valid():
            faculty = serializer.save()
            return Response({
                "message": "Faculty member created",
                "faculty": FacultySerializer(faculty).data
            }, status=201)

        return Response(serializer.errors, status=400)


# ------------------------------
# GET /faculty/{id}
# ------------------------------
class FacultyDetail(APIView):
    def get(self, request, pk):
        faculty = get_object_or_404(Faculty, pk=pk)
        return Response({"faculty": FacultySerializer(faculty).data})

    def put(self, request, pk):
        faculty = get_object_or_404(Faculty, pk=pk)
        serializer = FacultyCreateUpdateSerializer(faculty, data=request.data)
        if serializer.is_valid():
            serializer.save()
            return Response({
                "message": "Faculty updated",
                "faculty": FacultySerializer(faculty).data
            })
        return Response(serializer.errors, status=400)

    def patch(self, request, pk):
        faculty = get_object_or_404(Faculty, pk=pk)
        serializer = FacultyCreateUpdateSerializer(faculty, data=request.data, partial=True)
        if serializer.is_valid():
            serializer.save()
            return Response({
                "message": "Faculty updated",
                "faculty": FacultySerializer(faculty).data
            })
        return Response(serializer.errors, status=400)

    def delete(self, request, pk):
        faculty = get_object_or_404(Faculty, pk=pk)
        faculty.delete()
        return Response({"message": "Faculty member deleted"})


# ------------------------------
# GET /faculty/title/{title}
# ------------------------------
class FacultyByTitle(APIView):
    def get(self, request, title):
        queryset = Faculty.objects.filter(title__icontains=title)
        return Response({"faculty": FacultySerializer(queryset, many=True).data})


# ------------------------------
# GET /faculty/office/{office}
# ------------------------------
class FacultyByOffice(APIView):
    def get(self, request, office):
        queryset = Faculty.objects.filter(office__icontains=office)
        return Response({"faculty": FacultySerializer(queryset, many=True).data})


# ------------------------------
# GET /faculty/{id}/students
# ------------------------------
class FacultyWithStudents(APIView):
    def get(self, request, pk):
        faculty = get_object_or_404(Faculty, pk=pk)
        return Response({"faculty": FacultySerializer(faculty).data})

# Create your views here.
