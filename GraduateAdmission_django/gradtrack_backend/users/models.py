from django.contrib.auth.models import AbstractUser
from django.db import models

class User(AbstractUser):
    """
    Custom user model extending Django's AbstractUser.
    Matches Laravel fields:
    first_name, last_name, email, password, role, department
    """

    ROLE_CHOICES = (
        ('admin', 'Admin'),
        ('faculty', 'Faculty'),
        ('student', 'Student'),
    )

    email = models.EmailField(unique=True)
    role = models.CharField(max_length=20, choices=ROLE_CHOICES)
    department = models.CharField(max_length=100, blank=True, null=True)

    # Disable username if not used
    USERNAME_FIELD = "email"
    REQUIRED_FIELDS = []  # first_name/last_name optional

    def is_admin(self):
        return self.role == "admin"

    def is_faculty(self):
        return self.role == "faculty"

    def is_student(self):
        return self.role == "student"

    @property
    def full_name(self):
        return f"{self.first_name} {self.last_name}".strip()

    def __str__(self):
        return f"{self.email} ({self.role})"
from django.db import models

# Create your models here.
