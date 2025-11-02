from django.db import models
from django.conf import settings
from student.models import StudentProfile, ProgramOfStudy, Milestone
from django.db import models
User = settings.AUTH_USER_MODEL

# 教师基本信息
class FacultyProfile(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE)
    faculty_id = models.CharField(max_length=20, unique=True)
    department = models.CharField(max_length=100)
    position = models.CharField(max_length=50, blank=True)
    office = models.CharField(max_length=50, blank=True)
    phone = models.CharField(max_length=20, blank=True)

    def __str__(self):
        return f"{self.user.username} ({self.department})"


# 指导学生（多对多关系）
class FacultyAdvisee(models.Model):
    faculty = models.ForeignKey(FacultyProfile, on_delete=models.CASCADE)
    student = models.ForeignKey(StudentProfile, on_delete=models.CASCADE)
    assigned_date = models.DateField(auto_now_add=True)

    def __str__(self):
        return f"{self.faculty.user.username} → {self.student.user.username}"


# 审批请求
class ApprovalRequest(models.Model):
    faculty = models.ForeignKey(FacultyProfile, on_delete=models.CASCADE)
    student = models.ForeignKey(StudentProfile, on_delete=models.CASCADE)
    request_type = models.CharField(max_length=100)
    submitted_at = models.DateTimeField(auto_now_add=True)
    status = models.CharField(max_length=20, default='Pending')  # Pending / Approved / Rejected
    comments = models.TextField(blank=True)

    def __str__(self):
        return f"{self.request_type} ({self.student.student_id})"


# 评估报告
class EvaluationReport(models.Model):
    faculty = models.ForeignKey(FacultyProfile, on_delete=models.CASCADE)
    student = models.ForeignKey(StudentProfile, on_delete=models.CASCADE)
    report_title = models.CharField(max_length=100)
    report_body = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)
    score = models.IntegerField(null=True, blank=True)

    def __str__(self):
        return f"{self.student.student_id} - {self.report_title}"


# 导师通知
class FacultyNotification(models.Model):
    faculty = models.ForeignKey(FacultyProfile, on_delete=models.CASCADE)
    message = models.TextField()
    is_read = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Notification to {self.faculty.user.username}"


# Create your models here.
