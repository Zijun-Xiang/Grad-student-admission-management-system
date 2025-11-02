from django.db import models
from django.db import models
from django.contrib.auth.models import User

class Workflow(models.Model):
    name = models.CharField(max_length=100)
    status = models.CharField(max_length=50, choices=[
        ('active', 'Active'),
        ('paused', 'Paused'),
        ('completed', 'Completed'),
    ])
    created_at = models.DateTimeField(auto_now_add=True)

class Report(models.Model):
    title = models.CharField(max_length=200)
    created_at = models.DateTimeField(auto_now_add=True)
    description = models.TextField(blank=True)
    file = models.FileField(upload_to='reports/', null=True, blank=True)

class ComplianceItem(models.Model):
    name = models.CharField(max_length=100)
    due_date = models.DateField()
    status = models.CharField(max_length=50, choices=[
        ('pending', 'Pending'),
        ('completed', 'Completed')
    ])
    remarks = models.TextField(blank=True)

class SystemSetting(models.Model):
    key = models.CharField(max_length=100, unique=True)
    value = models.CharField(max_length=255)

# Create your models here.
