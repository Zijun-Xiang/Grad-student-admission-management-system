from django.db import models
from django.contrib.auth.models import AbstractUser
from django.db import models
# 用户模型（简化版，可后续扩展）
class User(AbstractUser):
    ROLE_CHOICES = [
        ('student', 'Student'),
        ('faculty', 'Faculty'),
        ('admin', 'Admin'),
    ]
    role = models.CharField(max_length=20, choices=ROLE_CHOICES, default='student')
    phone = models.CharField(max_length=20, blank=True)
    department = models.CharField(max_length=100, blank=True)

    def __str__(self):
        return f"{self.username} ({self.role})"


# 公告信息（对应 PublicHome.vue）
class Announcement(models.Model):
    title = models.CharField(max_length=150)
    content = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.title


# 常见问题（对应 PublicHelp.vue）
class FAQ(models.Model):
    question = models.CharField(max_length=255)
    answer = models.TextField()

    def __str__(self):
        return self.question


# 联系信息（对应 PublicContact.vue）
class ContactInfo(models.Model):
    department = models.CharField(max_length=100)
    contact_person = models.CharField(max_length=100)
    email = models.EmailField()
    phone = models.CharField(max_length=20, blank=True)

    def __str__(self):
        return f"{self.department} ({self.contact_person})"

# Create your models here.
