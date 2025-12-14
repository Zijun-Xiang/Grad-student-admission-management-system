from django.db import models
from django.conf import settings

User = settings.AUTH_USER_MODEL

class Student(models.Model):
    student_id = models.OneToOneField(
        User,
        primary_key=True,
        on_delete=models.CASCADE,
        related_name="student_record"
    )

    first_name = models.CharField(max_length=100)
    last_name = models.CharField(max_length=100)

    program_type = models.CharField(max_length=20, choices=[
        ("Masters", "Masters"),
        ("PhD", "PhD"),
    ])

    major_professor = models.ForeignKey(
        User,
        null=True,
        blank=True,
        on_delete=models.SET_NULL,
        related_name="advisee_students"
    )

    start_term = models.CharField(max_length=50)

    i9_status = models.CharField(max_length=20, choices=[
        ("Pending", "Pending"),
        ("Completed", "Completed"),
    ])

    deficiency_cleared = models.BooleanField(default=False)

    graduation_term = models.CharField(max_length=50, blank=True, null=True)

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    @property
    def full_name(self):
        return f"{self.first_name} {self.last_name}".strip()

    def __str__(self):
        return f"{self.student_id.id} - {self.full_name}"
