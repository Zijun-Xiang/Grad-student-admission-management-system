from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status


class AdvisorDetailView(APIView):
    def get(self, request, student_id):
        return Response({
            "message": "Advisor lookup not implemented",
            "studentId": student_id
        })


class AdvisorMessageView(APIView):
    def post(self, request):
        student_id = request.data.get("student_id")
        message = request.data.get("message")

        if not student_id or not message:
            return Response(
                {"error": "student_id and message are required"},
                status=status.HTTP_400_BAD_REQUEST
            )

        return Response({"message": "Message received"})
