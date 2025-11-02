from rest_framework import serializers
from .models import (
    FacultyProfile, FacultyAdvisee, ApprovalRequest,
    EvaluationReport, FacultyNotification
)
from student.models import StudentProfile

class FacultyProfileSerializer(serializers.ModelSerializer):
    class Meta:
        model = FacultyProfile
        fields = '__all__'


class FacultyAdviseeSerializer(serializers.ModelSerializer):
    student_name = serializers.CharField(source='student.user.username', read_only=True)
    student_id = serializers.CharField(source='student.student_id', read_only=True)

    class Meta:
        model = FacultyAdvisee
        fields = '__all__'


class ApprovalRequestSerializer(serializers.ModelSerializer):
    student_name = serializers.CharField(source='student.user.username', read_only=True)
    class Meta:
        model = ApprovalRequest
        fields = '__all__'


class EvaluationReportSerializer(serializers.ModelSerializer):
    student_name = serializers.CharField(source='student.user.username', read_only=True)
    class Meta:
        model = EvaluationReport
        fields = '__all__'


class FacultyNotificationSerializer(serializers.ModelSerializer):
    class Meta:
        model = FacultyNotification
        fields = '__all__'
