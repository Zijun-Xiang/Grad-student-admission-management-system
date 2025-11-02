from rest_framework import generics, permissions
from .models import *
from .serializers import *
from django.shortcuts import render
# Profile
class StudentProfileView(generics.RetrieveUpdateAPIView):
    queryset = StudentProfile.objects.all()
    serializer_class = StudentProfileSerializer
    permission_classes = [permissions.IsAuthenticated]

# Program of Study
class ProgramOfStudyListCreateView(generics.ListCreateAPIView):
    serializer_class = ProgramOfStudySerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return ProgramOfStudy.objects.filter(student__user=self.request.user)

class ProgramOfStudyUpdateView(generics.RetrieveUpdateDestroyAPIView):
    serializer_class = ProgramOfStudySerializer
    permission_classes = [permissions.IsAuthenticated]
    queryset = ProgramOfStudy.objects.all()

# Milestones
class MilestoneListCreateView(generics.ListCreateAPIView):
    serializer_class = MilestoneSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return Milestone.objects.filter(student__user=self.request.user)

# Committee
class CommitteeListView(generics.ListAPIView):
    serializer_class = CommitteeMemberSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return CommitteeMember.objects.filter(student__user=self.request.user)

# Documents
class StudentDocumentListCreateView(generics.ListCreateAPIView):
    serializer_class = StudentDocumentSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return StudentDocument.objects.filter(student__user=self.request.user)

# Notifications
class NotificationListView(generics.ListAPIView):
    serializer_class = NotificationSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return Notification.objects.filter(student__user=self.request.user)


# Create your views here.
