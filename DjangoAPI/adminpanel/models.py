from django.db import models
# 保留内置用户模型导入（后续可能会用到），当前未使用
from django.contrib.auth.models import User


# ========== 新增：可添加人员（前端“Add New User”用的实体） ==========
IDENTITY_CHOICES = [
    ('Faculty', 'Faculty'),
    ('Student', 'Student'),
]
class Person(models.Model):
    # 允许前端手动输入“学工号/人员ID”，避免与自增ID冲突，这里设为唯一
    user_id = models.CharField("ID", max_length=20, unique=True)
    name = models.CharField("Name", max_length=100)
    department = models.CharField("Department", max_length=100)
    identity = models.CharField("Identity", max_length=10, choices=IDENTITY_CHOICES, default='Student')
    # ✅ 新增密码字段（默认 #）
    password = models.CharField("Password", max_length=128, default="#")  ###########ZX
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "users"           # 数据库中的表名固定为 users（便于理解）
        ordering = ["-created_at"]

    def __str__(self):
        return f"{self.user_id} - {self.name}"


# ========== 你原有的模型，保持不变 ==========
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
