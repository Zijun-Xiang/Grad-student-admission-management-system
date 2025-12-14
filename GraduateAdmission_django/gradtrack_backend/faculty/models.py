from django.db import models
from django.conf import settings
from students.models import Student

User = settings.AUTH_USER_MODEL

class Faculty(models.Model):
    faculty_id = models.OneToOneField(
        User,
        primary_key=True,
        on_delete=models.CASCADE,
        related_name="faculty_record"
    )

    title = models.CharField(max_length=255)
    office = models.CharField(max_length=255, null=True, blank=True)

    created_at = models.DateTimeField(auto_now_add=True)

    @property
    def user(self):
        return self.faculty_id

    @property
    def full_name(self):
        if self.user:
            return f"{self.user.first_name} {self.user.last_name}".strip()
        return ""

    @property
    def title_with_name(self):
        return f"{self.title} {self.full_name}"

    def __str__(self):
        return f"{self.faculty_id_id} - {self.full_name}"
from django.db import models

# Create your models here.
