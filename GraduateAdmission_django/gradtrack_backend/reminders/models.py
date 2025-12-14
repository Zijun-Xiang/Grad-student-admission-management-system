from django.db import models
from django.conf import settings

User = settings.AUTH_USER_MODEL

class Reminder(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name="reminders")
    text = models.CharField(max_length=255)
    due_date = models.DateField(null=True, blank=True)
    priority = models.CharField(max_length=20, default="medium")
    is_complete = models.BooleanField(default=False)
    created_by = models.ForeignKey(
        User, on_delete=models.CASCADE, related_name="created_reminders"
    )

    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.text
