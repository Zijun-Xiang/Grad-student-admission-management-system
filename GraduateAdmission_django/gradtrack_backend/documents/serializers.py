from rest_framework import serializers
from .models import Document

class DocumentSerializer(serializers.ModelSerializer):
    class Meta:
        model = Document
        fields = [
            "id",
            "user",
            "file_name",
            "file_path",
            "file_type",
            "file_size",
            "tag",
            "is_required",
            "required_document_type",
            "status",
            "review_comment",
            "created_at",
        ]
        read_only_fields = ["id", "created_at", "file_path", "file_type", "file_size"]
