import { redirect } from "next/navigation";
import { headers } from "next/headers";
import { auth } from "@/lib/auth";
import { AdminSidebar } from "@/components/AdminSidebar";

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const headersList = await headers();
  const pathname = headersList.get("x-pathname") ?? "";

  // Login page: redirect already-authenticated users, otherwise render bare
  if (pathname.startsWith("/admin/login")) {
    const session = await auth();
    if (session) {
      redirect("/admin");
    }
    return <>{children}</>;
  }

  const session = await auth();

  if (!session) {
    redirect("/admin/login");
  }

  return (
    <div className="flex h-screen overflow-hidden bg-background">
      <AdminSidebar user={session.user} />
      <main className="flex-1 overflow-y-auto">
        <div className="p-6 lg:p-8 animate-in">{children}</div>
      </main>
    </div>
  );
}
