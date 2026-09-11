import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";

const prisma = new PrismaClient();

const PERMISSIONS = [
  // Applications
  { name: "can_view_applications", category: "applications" },
  { name: "can_create_applications", category: "applications" },
  { name: "can_edit_applications", category: "applications" },
  { name: "can_delete_applications", category: "applications" },
  { name: "can_assign_applications", category: "applications" },
  { name: "can_take_own_applications", category: "applications" },
  { name: "can_release_applications", category: "applications" },
  { name: "can_change_status", category: "applications" },

  // Users
  { name: "can_view_users", category: "users" },
  { name: "can_manage_users", category: "users" },

  // Roles
  { name: "can_view_roles", category: "roles" },
  { name: "can_manage_roles", category: "roles" },

  // System
  { name: "can_view_logs", category: "system" },
  { name: "can_manage_settings", category: "system" },

  // Reports
  { name: "can_view_reports", category: "reports" },
];

const ROLES = [
  {
    name: "super-admin",
    hierarchy: 0,
    isSystem: true,
    permissions: PERMISSIONS.map((p) => p.name),
  },
  {
    name: "admin",
    hierarchy: 1,
    isSystem: true,
    permissions: [
      "can_view_applications",
      "can_edit_applications",
      "can_assign_applications",
      "can_change_status",
      "can_view_users",
      "can_manage_users",
      "can_view_logs",
      "can_view_reports",
    ],
  },
  {
    name: "moderator",
    hierarchy: 2,
    isSystem: true,
    permissions: [
      "can_view_applications",
      "can_edit_applications",
      "can_assign_applications",
      "can_change_status",
      "can_view_reports",
    ],
  },
  {
    name: "operator",
    hierarchy: 3,
    isSystem: true,
    permissions: [
      "can_view_applications",
      "can_take_own_applications",
      "can_release_applications",
      "can_change_status",
    ],
  },
];

async function main() {
  console.log("🌱 Starting database seed...");

  // Clear existing data
  await prisma.rolePermission.deleteMany();
  await prisma.permission.deleteMany();
  await prisma.role.deleteMany();
  await prisma.user.deleteMany();

  // Create permissions
  console.log("📝 Creating permissions...");
  const permissionsCreated: Record<string, string> = {};
  for (const perm of PERMISSIONS) {
    const created = await prisma.permission.create({
      data: {
        name: perm.name,
        category: perm.category,
      },
    });
    permissionsCreated[perm.name] = created.id;
    console.log(`  ✓ ${perm.name}`);
  }

  // Create roles with permissions
  console.log("🎭 Creating roles...");
  const rolesCreated: Record<string, string> = {};
  for (const role of ROLES) {
    const created = await prisma.role.create({
      data: {
        name: role.name,
        hierarchy: role.hierarchy,
        isSystem: role.isSystem,
      },
    });
    rolesCreated[role.name] = created.id;
    console.log(`  ✓ ${role.name}`);

    // Assign permissions to role
    for (const permName of role.permissions) {
      await prisma.rolePermission.create({
        data: {
          roleId: created.id,
          permissionId: permissionsCreated[permName],
        },
      });
    }
  }

  // Create super admin user
  console.log("👤 Creating super admin user...");
  const superAdminPassword = process.env.SUPERADMIN_PASSWORD || "SuperAdmin@12345";
  const passwordHash = await bcrypt.hash(superAdminPassword, 12);

  const superAdmin = await prisma.user.create({
    data: {
      username: "Nikits_Sinitsin",
      email: "superadmin@kontanta.ru",
      fullName: "Super Administrator",
      passwordHash,
      roleId: rolesCreated["super-admin"],
      status: "ACTIVE",
    },
  });
  console.log(`  ✓ Nikits_Sinitsin (${superAdmin.email})`);
  console.log(`  📝 Password: ${superAdminPassword}`);

  // Create demo users for other roles
  console.log("👥 Creating demo users...");
  const demoUsers = [
    {
      username: "admin",
      email: "admin@kontanta.ru",
      fullName: "Administrator",
      role: "admin",
      password: "Admin@12345",
    },
    {
      username: "moderator",
      email: "moderator@kontanta.ru",
      fullName: "Moderator",
      role: "moderator",
      password: "Moderator@12345",
    },
    {
      username: "operator1",
      email: "operator1@kontanta.ru",
      fullName: "Operator One",
      role: "operator",
      password: "Operator@12345",
    },
    {
      username: "operator2",
      email: "operator2@kontanta.ru",
      fullName: "Operator Two",
      role: "operator",
      password: "Operator@12345",
    },
  ];

  for (const user of demoUsers) {
    const hash = await bcrypt.hash(user.password, 12);
    await prisma.user.create({
      data: {
        username: user.username,
        email: user.email,
        fullName: user.fullName,
        passwordHash: hash,
        roleId: rolesCreated[user.role],
        status: "ACTIVE",
      },
    });
    console.log(`  ✓ ${user.username}`);
  }

  console.log("\n✅ Database seed completed successfully!");
  console.log("\n📋 Test Credentials:");
  console.log("  Super Admin: Nikits_Sinitsin / SuperAdmin@12345");
  console.log("  Admin: admin / Admin@12345");
  console.log("  Moderator: moderator / Moderator@12345");
  console.log("  Operator: operator1 / Operator@12345");
}

main()
  .catch((e) => {
    console.error("❌ Seed error:", e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
