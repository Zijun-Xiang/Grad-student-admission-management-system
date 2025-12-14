from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status, permissions
from rest_framework.authtoken.models import Token
from django.contrib.auth import authenticate
from .models import User
from .serializers import UserSerializer, RegisterSerializer

# ------------------------------
# Register
# ------------------------------
class RegisterView(APIView):
    def post(self, request):
        ser = RegisterSerializer(data=request.data)
        if ser.is_valid():
            user = ser.save()
            Token.objects.create(user=user)  # auto create token
            return Response({"message": "User registered"}, status=201)
        return Response(ser.errors, status=400)

# ------------------------------
# Login
# ------------------------------
class LoginView(APIView):
    def post(self, request):
        email = request.data.get("email")
        password = request.data.get("password")

        user = authenticate(request, username=email, password=password)
        if not user:
            return Response({"error": "Invalid credentials"}, status=400)

        token, _ = Token.objects.get_or_create(user=user)
        return Response({"token": token.key})

# ------------------------------
# Logout
# ------------------------------
class LogoutView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def post(self, request):
        request.auth.delete()
        return Response({"message": "Logged out"})

# ------------------------------
# Get current user (/me)
# ------------------------------
class MeView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        return Response(UserSerializer(request.user).data)

# ------------------------------
# User CRUD
# ------------------------------
class UserList(APIView):
    def get(self, request):
        users = User.objects.all()
        return Response(UserSerializer(users, many=True).data)

    def post(self, request):
        ser = RegisterSerializer(data=request.data)
        if ser.is_valid():
            ser.save()
            return Response({"message": "User created"}, status=201)
        return Response(ser.errors, status=400)

class UserDetail(APIView):
    def get(self, request, pk):
        user = User.objects.get(pk=pk)
        return Response(UserSerializer(user).data)

    def delete(self, request, pk):
        user = User.objects.get(pk=pk)
        user.delete()
        return Response(status=204)

    def put(self, request, pk):
        user = User.objects.get(pk=pk)
        ser = RegisterSerializer(user, data=request.data, partial=False)
        if ser.is_valid():
            ser.save()
            return Response(UserSerializer(user).data)
        return Response(ser.errors, status=400)

    def patch(self, request, pk):
        user = User.objects.get(pk=pk)
        ser = RegisterSerializer(user, data=request.data, partial=True)
        if ser.is_valid():
            ser.save()
            return Response(UserSerializer(user).data)
        return Response(ser.errors, status=400)
from django.shortcuts import render

# Create your views here.
