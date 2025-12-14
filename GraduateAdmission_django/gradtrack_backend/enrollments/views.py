from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status
from django.shortcuts import get_object_or_404

from .models import Enrollment
from .serializers import EnrollmentSerializer


class EnrollmentList(APIView):
    def get(self, request):
        enrollments = Enrollment.objects.all()
        return Response(EnrollmentSerializer(enrollments, many=True).data)

    def post(self, request):
        serializer = EnrollmentSerializer(data=request.data)
        if serializer.is_valid():
            serializer.save()
            return Response(
                {
                    "message": "Enrollment created successfully",
                    "enrollment": serializer.data
                },
                status=201
            )
        return Response(serializer.errors, status=400)


class EnrollmentDetail(APIView):
    def put(self, request, pk):
        enrollment = get_object_or_404(Enrollment, pk=pk)
        serializer = EnrollmentSerializer(enrollment, data=request.data, partial=False)

        if serializer.is_valid():
            serializer.save()
            return Response({
                "message": "Enrollment updated successfully",
                "enrollment": serializer.data
            })

        return Response(serializer.errors, status=400)

    def patch(self, request, pk):
        enrollment = get_object_or_404(Enrollment, pk=pk)
        serializer = EnrollmentSerializer(enrollment, data=request.data, partial=True)

        if serializer.is_valid():
            serializer.save()
            return Response({
                "message": "Enrollment updated successfully",
                "enrollment": serializer.data
            })

        return Response(serializer.errors, status=400)

    def delete(self, request, pk):
        enrollment = get_object_or_404(Enrollment, pk=pk)
        enrollment.delete()
        return Response({"message": "Enrollment deleted successfully"})

