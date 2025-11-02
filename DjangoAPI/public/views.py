from rest_framework import generics, status
from rest_framework.response import Response
from rest_framework.views import APIView
from django.contrib.auth import authenticate
from rest_framework_simplejwt.tokens import RefreshToken
from .models import Announcement, FAQ, ContactInfo
from .serializers import AnnouncementSerializer, FAQSerializer, ContactInfoSerializer, UserLoginSerializer
from django.shortcuts import render
# 登录逻辑，对应 PublicLogin.vue
class LoginView(APIView):
    def post(self, request):
        serializer = UserLoginSerializer(data=request.data)
        if serializer.is_valid():
            username = serializer.validated_data['username']
            password = serializer.validated_data['password']
            user = authenticate(username=username, password=password)

            if user is not None:
                refresh = RefreshToken.for_user(user)
                return Response({
                    "message": "Login successful",
                    "username": user.username,
                    "role": user.role,
                    "access": str(refresh.access_token),
                    "refresh": str(refresh),
                })
            return Response({"error": "Invalid username or password"}, status=status.HTTP_401_UNAUTHORIZED)
        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


# 首页公告 (PublicHome.vue)
class AnnouncementListView(generics.ListAPIView):
    queryset = Announcement.objects.all().order_by('-created_at')
    serializer_class = AnnouncementSerializer


# 帮助页面常见问题 (PublicHelp.vue)
class FAQListView(generics.ListAPIView):
    queryset = FAQ.objects.all()
    serializer_class = FAQSerializer


# 联系页面 (PublicContact.vue)
class ContactListView(generics.ListAPIView):
    queryset = ContactInfo.objects.all()
    serializer_class = ContactInfoSerializer


# Create your views here.
