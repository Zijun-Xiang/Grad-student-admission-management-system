from rest_framework import serializers
from .models import Student
from users.serializers import UserSerializer
from users.models import User

class StudentSerializer(serializers.ModelSerializer):
    user = UserSerializer(read_only=True)
    major_professor = UserSerializer(read_only=True)

    class Meta:
        model = Student
        fields = [
            "student_id",
            "first_name",
            "last_name",
            "program_type",
            "major_professor",
            "major_professor_id",
            "start_term",
            "i9_status",
            "deficiency_cleared",
            "graduation_term",
            "created_at",
        ]

class StudentCreateUpdateSerializer(serializers.ModelSerializer):
    class Meta:
        model = Student
        fields = [
            "student_id",
            "first_name",
            "last_name",
            "program_type",
            "major_professor",
            "start_term",
            "i9_status",
            "deficiency_cleared",
            "graduation_term",
        ]
