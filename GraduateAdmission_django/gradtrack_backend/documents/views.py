from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status, permissions
from django.shortcuts import get_object_or_404
from django.http import FileResponse
from django.conf import settings
import os

from .models import Document
from .serializers import DocumentSerializer
from users.models import User


# -------------------------------------------
# GET /documents  (current user's documents)
# -------------------------------------------
class MyDocumentsView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        docs = Document.objects.filter(user=request.user).order_by("-created_at")
        return Response(DocumentSerializer(docs, many=True).data)


# -------------------------------------------
# POST /documents/upload
# -------------------------------------------
class DocumentUploadView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def post(self, request):
        file_obj = request.FILES.get("file")
        if not file_obj:
            return Response({"error": "file is required"}, status=400)

        filename = f"{int(round(file_obj.size))}_{file_obj.name}"
        user_id = request.user.id
        upload_path = f"documents/{user_id}/{filename}"
        full_path = os.path.join(settings.MEDIA_ROOT, upload_path)

        os.makedirs(os.path.dirname(full_path), exist_ok=True)

        # Save file
        with open(full_path, "wb+") as dest:
            for chunk in file_obj.chunks():
                dest.write(chunk)

        doc = Document.objects.create(
            user=request.user,
            file_name=file_obj.name,
            file_path=upload_path,
            file_type=file_obj.name.split(".")[-1],
            file_size=file_obj.size,
            tag=request.data.get("tag", "Untagged"),
            is_required=request.data.get("is_required", False),
            required_document_type=request.data.get("required_document_type"),
            status="Pending Review",
        )

        return Response({
            "message": "File uploaded successfully",
            "document": DocumentSerializer(doc).data
        }, status=201)


# -------------------------------------------
# GET /documents/{id}/download
# -------------------------------------------
class DocumentDownloadView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request, pk):
        doc = get_object_or_404(Document, pk=pk)
        user = request.user

        # Permission: owner OR admin/faculty with advisee match
        if not (user == doc.user or user.role in ["admin", "faculty"]):
            return Response({"error": "Unauthorized"}, status=403)

        if user.role == "faculty" and doc.user_id != user.id:
            advised_ids = user.advisee_students.values_list("student_id", flat=True)
            if doc.user_id not in advised_ids:
                return Response({"error": "Unauthorized"}, status=403)

        full_path = os.path.join(settings.MEDIA_ROOT, doc.file_path)
        if not os.path.exists(full_path):
            return Response({"error": "File not found"}, status=404)

        return FileResponse(open(full_path, "rb"), as_attachment=True, filename=doc.file_name)


# -------------------------------------------
# DELETE /documents/{id}
# -------------------------------------------
class DocumentDeleteView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def delete(self, request, pk):
        doc = get_object_or_404(Document, pk=pk)

        if request.user != doc.user:
            return Response({"error": "Unauthorized"}, status=403)

        full_path = os.path.join(settings.MEDIA_ROOT, doc.file_path)
        if os.path.exists(full_path):
            os.remove(full_path)

        doc.delete()
        return Response({"message": "Document deleted successfully"})


# -------------------------------------------
# GET /documents/{id} (metadata)
# -------------------------------------------
class DocumentDetailView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request, pk):
        doc = get_object_or_404(Document, pk=pk)
        user = request.user

        # Same permission rules as download
        if not (user == doc.user or user.role in ["admin", "faculty"]):
            return Response({"error": "Unauthorized"}, status=403)

        return Response(DocumentSerializer(doc).data)


# -------------------------------------------
# GET /documents/all (admin/faculty review list)
# -------------------------------------------
class AllDocumentsView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        user = request.user

        if user.role not in ["admin", "faculty"]:
            return Response({"error": "Unauthorized"}, status=403)

        docs = Document.objects.select_related("user").order_by("-created_at")

        if user.role == "faculty":
            advisee_ids = user.advisee_students.values_list("student_id", flat=True)
            docs = docs.filter(user_id__in=advisee_ids)

        data = []
        for d in docs:
            item = DocumentSerializer(d).data
            item["uploaded_by"] = (
                f"{d.user.first_name} {d.user.last_name}" if d.user else "Unknown"
            )
            data.append(item)

        return Response(data)


# -------------------------------------------
# PATCH/PUT /documents/{id}/status
# -------------------------------------------
class DocumentStatusUpdateView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def patch(self, request, pk):
        return self._update(request, pk)

    def put(self, request, pk):
        return self._update(request, pk)

    def _update(self, request, pk):
        doc = get_object_or_404(Document, pk=pk)
        user = request.user

        if user.role not in ["admin", "faculty"]:
            return Response({"error": "Unauthorized"}, status=403)

        # Decline rules
        status_value = request.data.get("status")
        comment = request.data.get("review_comment")

        if status_value == "Declined" and not comment:
            return Response({"error": "review_comment required"}, status=400)

        doc.status = status_value
        doc.review_comment = comment
        doc.save()

        return Response({
            "message": "Document status updated successfully",
            "document": DocumentSerializer(doc).data
        })
