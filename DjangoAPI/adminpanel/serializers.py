from rest_framework import serializers
from .models import Workflow, Report, ComplianceItem, SystemSetting, Person
from student.models import ChooseInstructor
from django.contrib.auth.models import User


# 系统用户（内置 User）
class UserSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = ['id', 'username', 'email', 'is_staff']


# ========== 新增：前端“Add New User”表单对应的模型序列化器 ==========
class PersonSerializer(serializers.ModelSerializer):
    class Meta:
        model = Person
        fields = '__all__'


# 工作流
class WorkflowSerializer(serializers.ModelSerializer):
    class Meta:
        model = Workflow
        fields = '__all__'


# 报表
class ReportSerializer(serializers.ModelSerializer):
    class Meta:
        model = Report
        fields = '__all__'


# 合规项
class ComplianceSerializer(serializers.ModelSerializer):
    class Meta:
        model = ComplianceItem
        fields = '__all__'


# 系统设置
class SystemSettingSerializer(serializers.ModelSerializer):
    class Meta:
        model = SystemSetting
        fields = '__all__'


class ChooseInstructorAdminSerializer(serializers.ModelSerializer):
    class Meta:
        model = ChooseInstructor
        fields = '__all__'

class RegisterSerializer(serializers.Serializer):
    user_id = serializers.CharField()
    password = serializers.CharField()

    # 登录用的序列化器


class LoginSerializer(serializers.Serializer):
    """登录用的序列化器"""
    user_id = serializers.CharField()
    password = serializers.CharField()

    def validate(self, data):
        user_id = data.get("user_id")
        password = data.get("password")

        # 1. 检查用户是否存在
        try:
            person = Person.objects.get(user_id=user_id)
        except Person.DoesNotExist:
            # 用户不存在
            raise serializers.ValidationError("User does not exist")

        # 2. 检查密码是否一致（目前是明文对比）
        if person.password != password:
            raise serializers.ValidationError("Incorrect password")

        # 3. 把查到的 person 存到 data 里，后面 View 直接用
        data["person"] = person
        return data


class RegisterSerializer(serializers.Serializer):
    user_id = serializers.CharField()
    password = serializers.CharField()

    def validate(self, data):
        user_id = data.get("user_id")
        password = data.get("password")

        # 检查 user 是否存在
        try:
            user = Person.objects.get(user_id=user_id)
        except Person.DoesNotExist:
            raise serializers.ValidationError("User ID not found.")

        # 不能重复注册
        if user.password != "#":
            raise serializers.ValidationError("This account has already been registered.")

        data["person"] = user
        return data

    def validate(self, data):
        user_id = data.get("user_id")
        password = data.get("password")

        # 检查 user 是否存在
        try:
            user = Person.objects.get(user_id=user_id)
        except Person.DoesNotExist:
            raise serializers.ValidationError("User ID not found.")

        # 不能重复注册
        if user.password != "#":
            raise serializers.ValidationError("This account has already been registered.")

        data["person"] = user
        return data
