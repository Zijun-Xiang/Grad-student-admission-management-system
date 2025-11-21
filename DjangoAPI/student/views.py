from django.shortcuts import get_object_or_404, render
from django.utils import timezone
from rest_framework import generics, permissions, status
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response

from faculty.models import FacultyProfile
from .models import *
from .serializers import *
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


@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def select_faculty_view(request):
    """Return available faculty list for the student to choose."""
    faculties = FacultyProfile.objects.all().values("id", "faculty_id", "department", "position")
    data = [
        {
            "id": f["id"],
            "facultyId": f["faculty_id"],
            "facultyName": f["faculty_id"],  # 前端可用 faculty_id 或再拉取用户姓名
            "department": f["department"],
            "position": f["position"],
        }
        for f in faculties
    ]
    return Response(data, status=status.HTTP_200_OK)


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def create_choose_instructor_view(request):
    """Create a pending ChooseInstructor record once the student selects a faculty."""
    student_profile = get_object_or_404(StudentProfile, user=request.user)
    faculty = get_object_or_404(FacultyProfile, id=request.data.get("faculty_id"))

    choose = ChooseInstructor.objects.create(
        facultyId=faculty.id,
        facultyName=faculty.user.get_full_name() or faculty.user.username,
        studentId=student_profile.id,
        studentName=student_profile.user.get_full_name() or student_profile.user.username,
        state=ChooseInstructor.STATE_PENDING,
    )
    return Response(
        {"id": choose.id, "studentId": student_profile.id},
        status=status.HTTP_201_CREATED,
    )


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def upload_file_view(request, pk):
    """Upload/replace file for an existing ChooseInstructor record (state unchanged)."""
    student_id = request.data.get("student_id") or request.POST.get("student_id")
    student_profile = None
    if student_id:
        student_profile = get_object_or_404(StudentProfile, id=student_id)
    else:
        student_profile = get_object_or_404(StudentProfile, user=request.user)
        student_id = student_profile.id

    choose = get_object_or_404(ChooseInstructor, id=pk, studentId=student_profile.id)

    uploaded_file = request.FILES.get("file")
    if not uploaded_file:
        return Response({"detail": "File is required."}, status=status.HTTP_400_BAD_REQUEST)

    choose.file = uploaded_file
    choose.save(update_fields=["file"])
    return Response({"detail": "File uploaded."}, status=status.HTTP_200_OK)


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def submit_for_review_view(request, pk):
    """
    Final student submit:
    - attach existing uploaded file (required)
    - save studentComment
    - set submittedAt = now
    - state remains pending
    """
    student_id = request.data.get("student_id") or request.POST.get("student_id")
    student_profile = None
    if student_id:
        student_profile = get_object_or_404(StudentProfile, id=student_id)
    else:
        student_profile = get_object_or_404(StudentProfile, user=request.user)
        student_id = student_profile.id

    choose = get_object_or_404(ChooseInstructor, id=pk, studentId=student_profile.id)

    if not choose.file:
        return Response({"detail": "Please upload a file before submitting."}, status=status.HTTP_400_BAD_REQUEST)

    choose.studentComment = request.data.get("studentComment", "")
    choose.submittedAt = timezone.now()
    choose.state = ChooseInstructor.STATE_PENDING  # 保持 pending
    choose.save(update_fields=["studentComment", "submittedAt", "state"])
    return Response({"detail": "Submitted for review."}, status=status.HTTP_200_OK)


@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def milestone_tracker_view(request):
    """Return the student's ChooseInstructor records with review status and file link."""
    student_profile = get_object_or_404(StudentProfile, user=request.user)
    records = ChooseInstructor.objects.filter(studentId=student_profile.id).order_by("-submittedAt")

    data = [
        {
            "id": ci.id,
            "facultyName": ci.facultyName,
            "state": ci.state,
            "facultyComment": ci.facultyComment,
            "studentComment": ci.studentComment,
            "submittedAt": ci.submittedAt,
            "reviewedAt": ci.reviewedAt,
            "file": ci.file.url if ci.file else None,
        }
        for ci in records
    ]
    return Response(data, status=status.HTTP_200_OK)
