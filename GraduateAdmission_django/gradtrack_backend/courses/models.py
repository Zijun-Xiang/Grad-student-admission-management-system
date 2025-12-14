from django.db import models

class Course(models.Model):
    LEVEL_CHOICES = (
        ("undergraduate", "Undergraduate"),
        ("graduate", "Graduate"),
    )

    course_code = models.CharField(max_length=20, unique=True)
    title = models.CharField(max_length=255)
    credits = models.IntegerField()
    level = models.CharField(max_length=20, choices=LEVEL_CHOICES)

    prerequisites = models.ManyToManyField(
        "self",
        symmetrical=False,
        related_name="is_prerequisite_for",
        blank=True,
    )

    def __str__(self):
        return f"{self.course_code} - {self.title}"


class PrerequisiteGroup(models.Model):
    course = models.ForeignKey(Course, on_delete=models.CASCADE, related_name="prerequisite_groups")
    prerequisites = models.ManyToManyField(Course, related_name="prerequisite_groups_items")

    def __str__(self):
        return f"Prerequisite Group for {self.course.course_code}"
