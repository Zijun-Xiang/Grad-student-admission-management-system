from rest_framework import serializers
from .models import Reminder

class ReminderSerializer(serializers.ModelSerializer):
    created_by = serializers.SerializerMethodField()

    class Meta:
        model = Reminder
        fields = [
            "id",
            "user",
            "text",
            "due_date",
            "priority",
            "is_complete",
            "created_by",
            "created_at",
        ]

    def get_created_by(self, reminder):
        user = reminder.created_by
        if user:
            return f"{user.first_name} {user.last_name}"
        return None
