from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status, permissions
from django.shortcuts import get_object_or_404

from .models import Student
from .serializers import StudentSerializer, StudentCreateUpdateSerializer
from users.models import User

# ------------------------------
# GET /students
# ------------------------------
class StudentList(APIView):
    def get(self, request):
        students = Student.objects.select_related("student_id", "major_professor").all()

        # Filters
        program_type = request.query_params.get("program_type")
        i9_status = request.query_params.get("i9_status")
        deficiency_cleared = request.query_params.get("deficiency_cleared")

        if program_type:
            students = students.filter(program_type=program_type)

        if i9_status:
            students = students.filter(i9_status=i9_status)

        if deficiency_cleared is not None:
            students = students.filter(deficiency_cleared=(deficiency_cleared == "true"))

        return Response({"students": StudentSerializer(students, many=True).data})

    def post(self, request):
        # user must exist and role=student
        student_user = get_object_or_404(User, id=request.data.get("student_id"))

        if student_user.role != "student":
            return Response(
                {"message": "User must have student role"},
                status=status.HTTP_422_UNPROCESSABLE_ENTITY
            )

        ser = StudentCreateUpdateSerializer(data=request.data)
        if ser.is_valid():
            student = ser.save()
            return Response({
                "message": "Student created",
                "student": StudentSerializer(student).data
            }, status=201)
        return Response(ser.errors, status=400)


# ------------------------------
# GET /students/{id}
# ------------------------------
class StudentDetail(APIView):
    def get(self, request, pk):
        student = get_object_or_404(Student, pk=pk)
        return Response({"student": StudentSerializer(student).data})

    def put(self, request, pk):
        student = get_object_or_404(Student, pk=pk)
        ser = StudentCreateUpdateSerializer(student, data=request.data, partial=False)
        if ser.is_valid():
            ser.save()
            return Response({"message": "Student updated", "student": StudentSerializer(student).data})
        return Response(ser.errors, status=400)

    def patch(self, request, pk):
        student = get_object_or_404(Student, pk=pk)
        ser = StudentCreateUpdateSerializer(student, data=request.data, partial=True)
        if ser.is_valid():
            ser.save()
            return Response({"message": "Student updated", "student": StudentSerializer(student).data})
        return Response(ser.errors, status=400)

    def delete(self, request, pk):
        student = get_object_or_404(Student, pk=pk)
        student.delete()
        return Response({"message": "Student deleted"})


# ------------------------------
# GET /students/program/{programType}
# ------------------------------
class StudentsByProgram(APIView):
    def get(self, request, program_type):
        students = Student.objects.filter(program_type=program_type)
        return Response({"students": StudentSerializer(students, many=True).data})


# ------------------------------
# GET /students/professor/{professorId}
# ------------------------------
class StudentsByProfessor(APIView):
    def get(self, request, professor_id):
        students = Student.objects.filter(major_professor_id=professor_id)
        return Response({"students": StudentSerializer(students, many=True).data})


# ------------------------------
# PUT/PATCH /students/{id}/advisor
# ------------------------------
class UpdateAdvisor(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def put(self, request, pk):
        return self._update(request, pk)

    def patch(self, request, pk):
        return self._update(request, pk)

    def _update(self, request, pk):
        student = get_object_or_404(Student, pk=pk)
        professor_id = request.data.get("major_professor_id")

        if professor_id:
            advisor = get_object_or_404(User, pk=professor_id)
            if advisor.role != "faculty":
                return Response(
                    {"message": "Advisor must be faculty"},
                    status=422
                )

        student.major_professor_id = professor_id
        student.save()

        return Response({
            "message": "Advisor updated",
            "student": StudentSerializer(student).data
        })
