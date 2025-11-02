from rest_framework import serializers
from .models import User, Announcement, FAQ, ContactInfo

class AnnouncementSerializer(serializers.ModelSerializer):
    class Meta:
        model = Announcement
        fields = '__all__'

class FAQSerializer(serializers.ModelSerializer):
    class Meta:
        model = FAQ
        fields = '__all__'

class ContactInfoSerializer(serializers.ModelSerializer):
    class Meta:
        model = ContactInfo
        fields = '__all__'

class UserLoginSerializer(serializers.Serializer):
    username = serializers.CharField()
    password = serializers.CharField()

'''from rest_framework import serializers
from EmployeeApp.models import Departments,Employees

class DepartmentSerializer(serializers.ModelSerializer):
    class Meta:
        model=Departments 
        fields=('DepartmentId','DepartmentName')

class EmployeeSerializer(serializers.ModelSerializer):
    class Meta:
        model=Employees 
        fields=('EmployeeId','EmployeeName','Department','DateOfJoining','PhotoFileName')'''