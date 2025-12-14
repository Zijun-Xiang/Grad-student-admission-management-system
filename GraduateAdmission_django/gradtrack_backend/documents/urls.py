from django.urls import path
from .views import (
    MyDocumentsView,
    DocumentUploadView,
    DocumentDownloadView,
    DocumentDeleteView,
    DocumentDetailView,
    AllDocumentsView,
    DocumentStatusUpdateView,
)

urlpatterns = [
    path("", MyDocumentsView.as_view()),
    path("upload/", DocumentUploadView.as_view()),
    path("all/", AllDocumentsView.as_view()),
    path("<int:pk>/", DocumentDetailView.as_view()),
    path("<int:pk>/download/", DocumentDownloadView.as_view()),
    path("<int:pk>/status/", DocumentStatusUpdateView.as_view()),
    path("<int:pk>/delete/", DocumentDeleteView.as_view()),
]
