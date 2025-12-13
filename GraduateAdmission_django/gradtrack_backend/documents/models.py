from django.db import models
from django.conf import settings

User = settings.AUTH_USER_MODEL

class Document(models.Model):
    user = models.ForeignKey(
        User,
        on_delete=models.CASCADE,
        related_name="documents"
    )

    file = models.FileField(upload_to="documents/%Y/%m/%d/")
    file_name = models.CharField(max_length=255)
    file_path = models.CharField(max_length=500)
    file_type = models.CharField(max_length=50)
    file_size = models.IntegerField()

    tag = models.CharField(max_length=255, default="Untagged")
    is_required = models.BooleanField(default=False)
    required_document_type = models.CharField(max_length=255, null=True, blank=True)

    status = models.CharField(max_length=50, default="Pending Review")
    review_comment = models.CharField(max_length=2000, null=True, blank=True)

    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.file_name} ({self.user})"
