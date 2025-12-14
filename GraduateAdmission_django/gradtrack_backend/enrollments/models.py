from django.db import models
from django.conf import settings
from students.models import Student
from courses.models import Course


class Enrollment(models.Model):
    STATUS_CHOICES = (
        ("enrolled", "Enrolled"),
        ("completed", "Completed"),
        ("dropped", "Dropped"),
    )

    student = models.ForeignKey(
        Student,
        on_delete=models.CASCADE,
        related_name="enrollments"
    )
    course = models.ForeignKey(
        Course,
        on_delete=models.CASCADE,
        related_name="enrollments"
    )

    term = models.CharField(max_length=50)

    status = models.CharField(
        max_length=20,
        choices=STATUS_CHOICES
    )

    grade = models.CharField(max_length=20, null=True, blank=True)

    def __str__(self):
        return f"{self.student.student_id_id} - {self.course.course_code} ({self.term})"
