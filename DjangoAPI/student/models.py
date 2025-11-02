from django.db import models
from django.conf import settings

User = settings.AUTH_USER_MODEL

# 学生基本资料
class StudentProfile(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE)
    student_id = models.CharField(max_length=20, unique=True)
    department = models.CharField(max_length=100)
    degree_program = models.CharField(max_length=100)
    advisor = models.CharField(max_length=100, blank=True)
    start_date = models.DateField(null=True, blank=True)
    expected_grad_date = models.DateField(null=True, blank=True)

    def __str__(self):
        return f"{self.user.username} ({self.student_id})"


# 学习计划
class ProgramOfStudy(models.Model):
    student = models.ForeignKey(StudentProfile, on_delete=models.CASCADE)
    course_code = models.CharField(max_length=10)
    course_title = models.CharField(max_length=100)
    semester = models.CharField(max_length=20)
    grade = models.CharField(max_length=5, blank=True, null=True)
    status = models.CharField(max_length=20, default="Planned")

    def __str__(self):
        return f"{self.course_code} ({self.student.student_id})"


# 里程碑（Milestone）
class Milestone(models.Model):
    student = models.ForeignKey(StudentProfile, on_delete=models.CASCADE)
    title = models.CharField(max_length=100)
    description = models.TextField()
    due_date = models.DateField()
    is_completed = models.BooleanField(default=False)

    def __str__(self):
        return f"{self.title} - {self.student.student_id}"


# 委员会成员
class CommitteeMember(models.Model):
    student = models.ForeignKey(StudentProfile, on_delete=models.CASCADE)
    name = models.CharField(max_length=100)
    role = models.CharField(max_length=50)  # Chair / Member
    email = models.EmailField()

    def __str__(self):
        return f"{self.name} ({self.role})"


# 文件上传
class StudentDocument(models.Model):
    student = models.ForeignKey(StudentProfile, on_delete=models.CASCADE)
    title = models.CharField(max_length=100)
    file = models.FileField(upload_to='student_documents/')
    uploaded_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.title} ({self.student.student_id})"


# 系统通知
class Notification(models.Model):
    student = models.ForeignKey(StudentProfile, on_delete=models.CASCADE)
    message = models.TextField()
    is_read = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Notification to {self.student.student_id}"
from django.db import models

# Create your models here.
