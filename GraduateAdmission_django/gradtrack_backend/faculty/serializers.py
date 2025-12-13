from rest_framework import serializers
from .models import Faculty
from users.serializers import UserSerializer
from students.serializers import StudentSerializer

class FacultySerializer(serializers.ModelSerializer):
    user = UserSerializer(read_only=True)
    advised_students = serializers.SerializerMethodField()

    class Meta:
        model = Faculty
        fields = [
            "faculty_id",
            "title",
            "office",
            "user",
            "advised_students",
            "created_at",
        ]

    def get_advised_students(self, obj):
        return StudentSerializer(obj.faculty_id.advisee_students.all(), many=True).data


class FacultyCreateUpdateSerializer(serializers.ModelSerializer):
    class Meta:
        model = Faculty
        fields = ["faculty_id", "title", "office"]
