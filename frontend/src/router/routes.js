const routes = [
  {
    path: "/",
    component: () => import("layouts/MainLayout.vue"),
    children: [
      { path: "", redirect: { name: "index" } },
      {
        path: "platforms",
        component: () => import("pages/Index.vue"),
        name: "index",
      },
      {
        path: "platforms/create",
        component: () => import("pages/PlatformCreateAndEdit.vue"),
      },
      {
        path: "platforms/:id/edit",
        component: () => import("pages/PlatformCreateAndEdit.vue"),
      },
      {
        path: "operation-logs",
        component: () => import("pages/OperationLog.vue"),
        name: "operationLogs",
      },
    ],
  },
  {
    path: "/open-platform",
    component: () => import("layouts/ManageLayout.vue"),
    children: [
      {
        path: ":id",
        component: () => import("pages/op/Show.vue"),
      },
      {
        path: ":id/domain",
        component: () => import("pages/op/Domain.vue"),
      },
      {
        path: ":id/code-manage",
        component: () => import("pages/op/CodeManage.vue"),
      },
    ],
  },

  {
    path: "/open-platform/:opId/mini-program/:appId/",
    component: () => import("layouts/SubMiniProgramLayout.vue"),
    children: [
      {
        path: "",
        component: () => import("pages/mp/Index.vue"),
        name: "subMiniProgramIndex",
      },
      {
        path: "qr",
        component: () => import("pages/mp/QR.vue"),
        name: "mpQR",
      },
      {
        path: "basic-information",
        component: () => import("pages/mp/BasicInformation.vue"),
        name: "basicInformation",
      },
      {
        path: "category",
        component: () => import("pages/mp/Category.vue"),
        name: "category",
      },
      {
        path: "privacy",
        component: () => import("pages/mp/Privacy.vue"),
        name: "privacy",
      },
      {
        path: "trust-domain",
        component: () => import("pages/mp/DomainSettings.vue"),
        name: "trustDomain",
      },
      {
        path: "tester",
        component: () => import("pages/mp/Tester.vue"),
        name: "tester",
      },
      {
        path: "media-manage",
        component: () => import("pages/mp/MediaManage.vue"),
        name: "mediaManage",
      },
      {
        path: "code-manage",
        component: () => import("pages/mp/CodeManage.vue"),
        name: "codeManage",
      },
      {
        path: "code-manage-audit",
        component: () => import("pages/mp/CodeManageAudit.vue"),
        name: "codeManageAudit",
      },
    ],
  },

  {
    path: "/open-platform/:opId/official-account/:appId/",
    component: () => import("layouts/SubOfficialAccountLayout.vue"),
    children: [
      {
        path: "",
        component: () => import("pages/oa/Index.vue"),
        name: "subOfficialAccountIndex",
      },
      {
        path: "custom-menu",
        component: () => import("pages/oa/CustomMenu.vue"),
        name: "customMenu",
      },
    ],
  },

  {
    path: "/",
    component: () => import("layouts/NoAuthLayout.vue"),
    children: [
      {
        path: "login",
        component: () => import("pages/Login.vue"),
        name: "login",
      },
      // Always leave this as last one,
      // but you can also remove it
      {
        path: "/401",
        component: () => import("pages/PageError401.vue"),
        name: "401",
      },
      {
        path: "/:catchAll(.*)*",
        component: () => import("pages/PageError404.vue"),
        name: "404",
      },
    ],
  },
];

export default routes;
