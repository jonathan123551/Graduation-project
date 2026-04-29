import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
  BrowserRouter,
  Routes,
  Route,
  useLocation,
} from "react-router-dom";
import { AnimatePresence } from "framer-motion";

import { LanguageProvider } from "@/i18n/LanguageContext";
import { AuthProvider } from "@/contexts/AuthContext";
import { ThemeProvider } from "@/contexts/ThemeContext";

import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import PageTransition from "@/components/PageTransition";
import ProtectedRoute from "@/components/ProtectedRoute";

import Landing from "@/pages/Landing";
import Login from "@/pages/Login";
import Register from "@/pages/Register";
import ForgotPassword from "@/pages/ForgotPassword";
import ResetPassword from "@/pages/ResetPassword";
import Dashboard from "@/pages/Dashboard";
import SubmitIdea from "@/pages/SubmitIdea";
import Marketplace from "@/pages/Marketplace";
import IdeaDetail from "@/pages/IdeaDetail";
import AiChat from "@/pages/AiChat";
import ChatWithFounder from "@/pages/ChatWithFounder";
import KycVerification from "@/pages/KycVerification";
import MyDeals from "@/pages/MyDeals";
import Payment from "@/pages/Payment";
import Admin from "@/pages/Admin";
import VerifyPhone from "@/pages/VerifyPhone";
import NotFound from "@/pages/NotFound";

const queryClient = new QueryClient();

function AnimatedRoutes() {
  const location = useLocation();

  return (
    <AnimatePresence mode="wait">
      <Routes
        location={location}
        key={location.pathname}
      >
        {/* Public Routes */}
        <Route
          path="/"
          element={
            <PageTransition>
              <Landing />
            </PageTransition>
          }
        />

        <Route
          path="/login"
          element={
            <PageTransition>
              <Login />
            </PageTransition>
          }
        />

        <Route
          path="/register"
          element={
            <PageTransition>
              <Register />
            </PageTransition>
          }
        />

        <Route
          path="/forgot-password"
          element={
            <PageTransition>
              <ForgotPassword />
            </PageTransition>
          }
        />

        <Route
          path="/reset-password"
          element={
            <PageTransition>
              <ResetPassword />
            </PageTransition>
          }
        />

        <Route
          path="/marketplace"
          element={
            <PageTransition>
              <Marketplace />
            </PageTransition>
          }
        />

        <Route
          path="/idea/:id"
          element={
            <PageTransition>
              <IdeaDetail />
            </PageTransition>
          }
        />

        {/* Protected Routes */}
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <PageTransition>
                <Dashboard />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        <Route
          path="/submit"
          element={
            <ProtectedRoute>
              <PageTransition>
                <SubmitIdea />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        <Route
          path="/chat"
          element={
            <ProtectedRoute>
              <PageTransition>
                <AiChat />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        <Route
          path="/chat-founder/:founderId"
          element={
            <ProtectedRoute>
              <PageTransition>
                <ChatWithFounder />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        <Route
          path="/kyc"
          element={
            <ProtectedRoute>
              <PageTransition>
                <KycVerification />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        <Route
          path="/verify-phone"
          element={
            <ProtectedRoute>
              <PageTransition>
                <VerifyPhone />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        <Route
          path="/deals"
          element={
            <ProtectedRoute>
              <PageTransition>
                <MyDeals />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        <Route
          path="/pay"
          element={
            <ProtectedRoute>
              <PageTransition>
                <Payment />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        {/* Admin Only */}
        <Route
          path="/admin"
          element={
            <ProtectedRoute
              allowedRoles={["admin"]}
            >
              <PageTransition>
                <Admin />
              </PageTransition>
            </ProtectedRoute>
          }
        />

        {/* 404 */}
        <Route
          path="*"
          element={
            <PageTransition>
              <NotFound />
            </PageTransition>
          }
        />
      </Routes>
    </AnimatePresence>
  );
}

const App = () => (
  <QueryClientProvider client={queryClient}>
    <LanguageProvider>
      <ThemeProvider>
        <AuthProvider>
          <TooltipProvider>
            <Toaster />
            <Sonner />

            <BrowserRouter>
              <Navbar />
              <AnimatedRoutes />
              <Footer />
            </BrowserRouter>
          </TooltipProvider>
        </AuthProvider>
      </ThemeProvider>
    </LanguageProvider>
  </QueryClientProvider>
);

export default App;