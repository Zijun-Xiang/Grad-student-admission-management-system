from django.urls import path, re_path, include
from EmployeeApp import views

from django.conf.urls.static import static
from django.conf import settings

urlpatterns=[
                # Department API
                path("department/", views.departmentApi, name="department-list"),
                path("department/<int:id>/", views.departmentApi, name="department-detail"),

                # Employee API
                path("employee/", views.employeeApi, name="employee-list"),
                path("employee/<int:id>/", views.employeeApi, name="employee-detail"),

                # File Upload
                path("employee/savefile/", views.SaveFile, name="employee-upload"),
]+static(settings.MEDIA_URL,document_root=settings.MEDIA_ROOT)