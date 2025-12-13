from rest_framework.views import APIView
from rest_framework.response import Response

from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status
from .serializers import RegisterSerializer


class RegisterView(APIView):
    def post(self, request):
        serializer = RegisterSerializer(data=request.data)

        if serializer.is_valid():
            serializer.save()
            return Response(
                {"message": "User registered successfully"},
                status=status.HTTP_201_CREATED
            )

        return Response(
            serializer.errors,
            status=status.HTTP_400_BAD_REQUEST
        )

class MajorCompletionView(APIView):
    def get(self, request, student_id):
        # Placeholder logic matching Laravel behavior
        completed_credits = 5
        required_credits = 30

        percentage = round((completed_credits / required_credits) * 100, 2)

        return Response({
            "completed": completed_credits,
            "required": required_credits,
            "percentage": percentage
        })
