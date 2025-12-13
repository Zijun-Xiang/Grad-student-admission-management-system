from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status, permissions
from django.shortcuts import get_object_or_404

from .models import Reminder
from .serializers import ReminderSerializer
from users.models import User


class ReminderListView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        reminders = Reminder.objects.filter(user=request.user).order_by("-created_at")
        return Response(ReminderSerializer(reminders, many=True).data)

    def post(self, request):
        serializer = ReminderSerializer(data=request.data)

        if serializer.is_valid():
            reminder = Reminder.objects.create(
                user=request.user,
                created_by=request.user,
                text=serializer.validated_data["text"],
                due_date=serializer.validated_data.get("due_date"),
                priority=serializer.validated_data.get("priority", "medium"),
            )
            return Response(ReminderSerializer(reminder).data, status=201)

        return Response(serializer.errors, status=400)


class SendReminderToStudentView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def post(self, request, student_id):
        user = request.user

        if user.role not in ["admin", "faculty"]:
            return Response({"error": "Unauthorized"}, status=403)

        student = get_object_or_404(User, pk=student_id, role="student")

        text = request.data.get("text")
        if not text:
            return Response({"error": "text required"}, status=400)

        reminder = Reminder.objects.create(
            user=student,
            created_by=user,
            text=text,
            due_date=request.data.get("due_date"),
            priority=request.data.get("priority", "medium"),
        )

        return Response({
            "message": "Reminder sent successfully",
            "reminder": ReminderSerializer(reminder).data
        }, status=201)


class ReminderDetailView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def patch(self, request, pk):
        reminder = get_object_or_404(Reminder, pk=pk, user=request.user)
        reminder.text = request.data.get("text", reminder.text)
        reminder.due_date = request.data.get("due_date", reminder.due_date)
        reminder.priority = request.data.get("priority", reminder.priority)
        reminder.is_complete = request.data.get("is_complete", reminder.is_complete)
        reminder.save()
        return Response(ReminderSerializer(reminder).data)

    def delete(self, request, pk):
        reminder = get_object_or_404(Reminder, pk=pk, user=request.user)
        reminder.delete()
        return Response({"message": "Reminder deleted"})
