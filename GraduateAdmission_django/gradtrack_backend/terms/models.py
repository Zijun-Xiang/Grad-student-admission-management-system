from django.db import models
from students.models import Student
from courses.models import Course


class Term(models.Model):
    student = models.ForeignKey(
        Student,
        on_delete=models.CASCADE,
        related_name="terms"
    )

    name = models.CharField(max_length=255, null=True, blank=True)
    order = models.IntegerField()

    def __str__(self):
        return f"{self.student.student_id_id} - {self.name}"


class TermCourse(models.Model):
    term = models.ForeignKey(Term, on_delete=models.CASCADE)
    course = models.ForeignKey(Course, on_delete=models.CASCADE)

    class Meta:
        unique_together = ("term", "course")

    def __str__(self):
        return f"{self.term} - {self.course.course_code}"

