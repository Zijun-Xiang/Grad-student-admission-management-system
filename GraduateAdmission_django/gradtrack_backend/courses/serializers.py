from rest_framework import serializers
from .models import Course, PrerequisiteGroup

class CourseSerializer(serializers.ModelSerializer):
    prerequisite_groups = serializers.SerializerMethodField()

    class Meta:
        model = Course
        fields = [
            "id",
            "course_code",
            "title",
            "credits",
            "level",
            "prerequisites",
            "prerequisite_groups",
        ]

    def get_prerequisite_groups(self, obj):
        groups = obj.prerequisite_groups.all()
        return [
            {
                "group_id": g.id,
                "prerequisites": [c.course_code for c in g.prerequisites.all()],
            }
            for g in groups
        ]


class CourseCreateUpdateSerializer(serializers.ModelSerializer):
    class Meta:
        model = Course
        fields = ["course_code", "title", "credits", "level"]


class PrerequisiteGroupSerializer(serializers.ModelSerializer):
    class Meta:
        model = PrerequisiteGroup
        fields = ["id", "course", "prerequisites"]
