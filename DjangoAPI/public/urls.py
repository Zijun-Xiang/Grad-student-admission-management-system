from django.urls import path
from .views import LoginView, AnnouncementListView, FAQListView, ContactListView

urlpatterns = [
    path('login/', LoginView.as_view(), name='login'),
    path('home/', AnnouncementListView.as_view(), name='home'),
    path('help/', FAQListView.as_view(), name='help'),
    path('contact/', ContactListView.as_view(), name='contact'),
]
'''from django.urls import path, re_path, include
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
]+static(settings.MEDIA_URL,document_root=settings.MEDIA_ROOT)'''