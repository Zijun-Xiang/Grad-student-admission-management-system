from rest_framework import serializers
from .models import (
    StudentProfile, ProgramOfStudy, Milestone,
    CommitteeMember, StudentDocument, Notification
)

class StudentProfileSerializer(serializers.ModelSerializer):
    class Meta:
        model = StudentProfile
        fields = '__all__'

class ProgramOfStudySerializer(serializers.ModelSerializer):
    class Meta:
        model = ProgramOfStudy
        fields = '__all__'

class MilestoneSerializer(serializers.ModelSerializer):
    class Meta:
        model = Milestone
        fields = '__all__'

class CommitteeMemberSerializer(serializers.ModelSerializer):
    class Meta:
        model = CommitteeMember
        fields = '__all__'

class StudentDocumentSerializer(serializers.ModelSerializer):
    class Meta:
        model = StudentDocument
        fields = '__all__'

class NotificationSerializer(serializers.ModelSerializer):
    class Meta:
        model = Notification
        fields = '__all__'
